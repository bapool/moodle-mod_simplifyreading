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

/**
 * Student interface for Simplify Text: rewrite, redo and read aloud.
 *
 * @module     mod_simplifyreading/simplify
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

/** Longest piece of text given to the speech engine at once (Chrome stops on long utterances). */
const MAX_CHUNK = 220;

/**
 * Count words the same way the server does.
 *
 * @param {string} text
 * @returns {number}
 */
const countWords = (text) => {
    const trimmed = text.trim();
    return trimmed === '' ? 0 : trimmed.split(/\s+/).length;
};

/**
 * Split text into short pieces for the speech engine.
 *
 * @param {string} text
 * @returns {string[]}
 */
const splitForSpeech = (text) => {
    const chunks = [];
    text.split(/\n+/).forEach((line) => {
        const sentences = line.match(/[^.!?]+[.!?]+["')\]]*|[^.!?]+$/g) || [];
        sentences.forEach((sentence) => {
            let piece = sentence.trim();
            while (piece.length > MAX_CHUNK) {
                let cut = piece.lastIndexOf(',', MAX_CHUNK);
                if (cut < 40) {
                    cut = piece.lastIndexOf(' ', MAX_CHUNK);
                }
                if (cut < 1) {
                    cut = MAX_CHUNK;
                }
                chunks.push(piece.slice(0, cut + 1).trim());
                piece = piece.slice(cut + 1).trim();
            }
            if (piece !== '') {
                chunks.push(piece);
            }
        });
    });
    return chunks;
};

/**
 * Set up one Simplify Text interface.
 *
 * @param {string} rootId Id of the outer element.
 */
export const init = (rootId) => {
    const root = document.getElementById(rootId);
    if (!root) {
        return;
    }

    const cmid = parseInt(root.dataset.cmid, 10);
    const maxWords = parseInt(root.dataset.maxwords, 10);

    const input = root.querySelector('[data-region="input"]');
    const wordCount = root.querySelector('[data-region="wordcount"]');
    const rewriteButton = root.querySelector('[data-action="rewrite"]');
    const redoButton = root.querySelector('[data-action="redo"]');
    const errorBox = root.querySelector('[data-region="error"]');
    const loading = root.querySelector('[data-region="loading"]');
    const result = root.querySelector('[data-region="result"]');
    const output = root.querySelector('[data-region="output"]');
    const readButton = root.querySelector('[data-action="readaloud"]');
    const stopButton = root.querySelector('[data-action="stop"]');
    const speedSelect = root.querySelector('[data-region="speed"]');
    const speechControls = root.querySelector('[data-region="speechcontrols"]');
    const noSpeech = root.querySelector('[data-region="nospeech"]');

    const speechSupported = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;

    let lastText = '';
    let attempt = 0;
    let busy = false;
    let speaking = false;
    let speechRun = 0;

    // Word count.

    const updateWordCount = async() => {
        const count = countWords(input.value);
        wordCount.textContent = await getString('wordcount', 'mod_simplifyreading', {count: count, max: maxWords});
        wordCount.classList.toggle('text-danger', count > maxWords);
        wordCount.classList.toggle('text-muted', count <= maxWords);
    };

    // Speech.

    const setSpeakingState = (isSpeaking) => {
        speaking = isSpeaking;
        readButton.disabled = isSpeaking;
        stopButton.disabled = !isSpeaking;
    };

    const stopSpeech = () => {
        speechRun++;
        if (speechSupported) {
            window.speechSynthesis.cancel();
        }
        setSpeakingState(false);
    };

    const readAloud = () => {
        if (!speechSupported) {
            return;
        }
        stopSpeech();
        const chunks = splitForSpeech(output.innerText || '');
        if (!chunks.length) {
            return;
        }
        const rate = parseFloat(speedSelect.value) || 1;
        const lang = document.documentElement.lang || 'en';
        // Each reading gets its own id, so events from a cancelled reading are ignored.
        const thisRun = ++speechRun;
        let index = 0;

        const speakNext = () => {
            if (thisRun !== speechRun || !speaking) {
                return;
            }
            if (index >= chunks.length) {
                setSpeakingState(false);
                return;
            }
            const utterance = new window.SpeechSynthesisUtterance(chunks[index]);
            utterance.rate = rate;
            utterance.lang = lang;
            utterance.onend = () => {
                index++;
                speakNext();
            };
            utterance.onerror = () => {
                if (thisRun === speechRun) {
                    setSpeakingState(false);
                }
            };
            window.speechSynthesis.speak(utterance);
        };

        setSpeakingState(true);
        speakNext();
    };

    // Rewrite.

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    };

    const setBusy = (isBusy) => {
        busy = isBusy;
        rewriteButton.disabled = isBusy;
        redoButton.disabled = isBusy;
        loading.classList.toggle('d-none', !isBusy);
    };

    const runRewrite = async(text) => {
        if (busy) {
            return;
        }
        errorBox.classList.add('d-none');
        stopSpeech();

        const count = countWords(text);
        if (count === 0) {
            showError(await getString('errornotext', 'mod_simplifyreading'));
            return;
        }
        if (count > maxWords) {
            showError(await getString('errortoolong', 'mod_simplifyreading', {count: count, max: maxWords}));
            return;
        }

        setBusy(true);
        try {
            const response = await Ajax.call([{
                methodname: 'mod_simplifyreading_simplify_text',
                args: {cmid: cmid, text: text, attempt: attempt},
            }])[0];

            if (response.success) {
                output.innerHTML = response.html;
                result.classList.remove('d-none');
                output.focus();
                result.scrollIntoView({behavior: 'smooth', block: 'start'});
            } else {
                showError(response.errormessage);
            }
        } catch (error) {
            Notification.exception(error);
        } finally {
            setBusy(false);
        }
    };

    // Events.

    input.addEventListener('input', updateWordCount);

    rewriteButton.addEventListener('click', () => {
        lastText = input.value;
        attempt = 1;
        runRewrite(lastText);
    });

    redoButton.addEventListener('click', () => {
        attempt++;
        runRewrite(lastText);
    });

    if (speechSupported) {
        readButton.addEventListener('click', readAloud);
        stopButton.addEventListener('click', stopSpeech);
        speedSelect.addEventListener('change', () => {
            if (speaking) {
                readAloud();
            }
        });
        window.addEventListener('beforeunload', stopSpeech);
    } else {
        speechControls.classList.add('d-none');
        noSpeech.classList.remove('d-none');
    }

    updateWordCount();
};
