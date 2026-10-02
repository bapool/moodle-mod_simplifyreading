<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_simplifyreading\local;

/**
 * Builds the AI prompt, calls the Moodle AI subsystem and formats the result.
 *
 * Nothing the student pastes, and nothing the AI returns, is saved by this plugin.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplifier {
    /** @var int Word limit used when the site setting is empty. */
    public const DEFAULT_MAX_WORDS = 3000;

    /**
     * Site-wide word limit for pasted text.
     *
     * @return int
     */
    public static function get_max_words(): int {
        $max = (int) get_config('mod_simplifyreading', 'maxwords');
        return $max > 0 ? $max : self::DEFAULT_MAX_WORDS;
    }

    /**
     * Count words the same way the browser does (runs of non-whitespace).
     *
     * @param string $text Text to count.
     * @return int
     */
    public static function count_words(string $text): int {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }
        return count(preg_split('/\s+/u', $text));
    }

    /**
     * Normalise pasted text: plain text only, tidy line breaks.
     *
     * @param string $text Raw pasted text.
     * @return string
     */
    public static function clean_input(string $text): string {
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    /**
     * Build the full prompt sent to the AI.
     *
     * The pasted text is appended after the language strings rather than
     * passed through get_string(), so placeholders in student text are never
     * expanded.
     *
     * @param \stdClass $instance Simplify Text instance record.
     * @param string $text Cleaned student text.
     * @param int $attempt 1 for the first rewrite, 2+ when the student clicks Redo.
     * @return string
     */
    public static function build_prompt(\stdClass $instance, string $text, int $attempt): string {
        $grade = (int) $instance->gradelevel;
        $lexile = simplifyreading_get_lexile($grade);

        $parts = [];
        $parts[] = get_string('aiprompt', 'mod_simplifyreading', (object) ['grade' => $grade, 'lexile' => $lexile]);

        $instructions = trim((string) $instance->aiinstructions);
        if ($instructions !== '') {
            $parts[] = get_string('aipromptteacher', 'mod_simplifyreading') . "\n" . $instructions;
        }

        if ($attempt > 1) {
            $parts[] = get_string('aipromptredo', 'mod_simplifyreading');
        }

        $parts[] = get_string('aiprompttext', 'mod_simplifyreading') . "\n\"\"\"\n" . $text . "\n\"\"\"";

        return implode("\n\n", $parts);
    }

    /**
     * Remove things the model sometimes adds around its answer.
     *
     * @param string $output Raw AI output.
     * @return string Markdown text.
     */
    public static function clean_output(string $output): string {
        // Reasoning blocks from thinking models.
        $output = preg_replace('~<think>.*?</think>~is', '', $output);
        // A code fence (three backtick characters, \x60) wrapped around the whole answer.
        $output = preg_replace('~^\s*\x60{3}[a-z]*\s*\n~i', '', $output);
        $output = preg_replace('~\n\s*\x60{3}\s*$~', '', $output);
        // Stray delimiters copied from the prompt.
        $output = str_replace('"""', '', $output);
        return trim($output);
    }

    /**
     * Send text to the AI and return HTML ready to show the student.
     *
     * @param \stdClass $instance Simplify Text instance record.
     * @param \context_module $context Module context.
     * @param int $userid Student user id.
     * @param string $rawtext Text as pasted by the student.
     * @param int $attempt Rewrite number (1 = first, 2+ = Redo).
     * @return array ['success' => bool, 'html' => string, 'errormessage' => string]
     */
    public static function simplify(
        \stdClass $instance,
        \context_module $context,
        int $userid,
        string $rawtext,
        int $attempt
    ): array {
        $text = self::clean_input($rawtext);
        $wordcount = self::count_words($text);
        $maxwords = self::get_max_words();

        if ($wordcount === 0) {
            return self::error(get_string('errornotext', 'mod_simplifyreading'));
        }
        if ($wordcount > $maxwords) {
            return self::error(get_string(
                'errortoolong',
                'mod_simplifyreading',
                (object) ['count' => $wordcount, 'max' => $maxwords]
            ));
        }

        $prompt = self::build_prompt($instance, $text, max(1, $attempt));
        $canseedetail = has_capability('moodle/course:manageactivities', $context);

        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $context->id,
                userid: $userid,
                prompttext: $prompt,
            );
            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);
        } catch (\Throwable $e) {
            debugging('mod_simplifyreading AI call failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return self::error(
                get_string('erroraifailed', 'mod_simplifyreading'),
                $canseedetail ? $e->getMessage() : ''
            );
        }

        if (!$response->get_success()) {
            return self::error(
                get_string('erroraifailed', 'mod_simplifyreading'),
                $canseedetail ? (string) $response->get_errormessage() : ''
            );
        }

        $data = $response->get_response_data();
        $markdown = self::clean_output((string) ($data['generatedcontent'] ?? ''));
        if ($markdown === '') {
            return self::error(get_string('erroraifailed', 'mod_simplifyreading'));
        }

        $html = format_text($markdown, FORMAT_MARKDOWN, ['context' => $context, 'para' => false]);

        $event = \mod_simplifyreading\event\text_simplified::create([
            'objectid' => $instance->id,
            'context' => $context,
            'other' => [
                'wordcount' => $wordcount,
                'attempt' => max(1, $attempt),
            ],
        ]);
        $event->trigger();

        return [
            'success' => true,
            'html' => $html,
            'errormessage' => '',
        ];
    }

    /**
     * Build a failure result.
     *
     * @param string $message Message for the student.
     * @param string $detail Technical detail, shown to teachers only.
     * @return array
     */
    protected static function error(string $message, string $detail = ''): array {
        if ($detail !== '') {
            $message .= ' (' . $detail . ')';
        }
        return [
            'success' => false,
            'html' => '',
            'errormessage' => $message,
        ];
    }
}
