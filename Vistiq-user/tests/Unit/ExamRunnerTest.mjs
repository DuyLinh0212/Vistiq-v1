import test from 'node:test';
import assert from 'node:assert/strict';
import { remainingSeconds, reconcileAnswers, recordingFilename } from '../../resources/js/exam-runner.js';

test('timer uses server offset and clamps expired deadlines', () => {
    const time = Date.parse('2026-10-05T10:00:00Z');
    assert.equal(remainingSeconds('2026-10-05T10:01:00Z', 10000, time), 50);
    assert.equal(remainingSeconds('2026-10-05T09:59:00Z', 0, time), 0);
    assert.equal(remainingSeconds('2026-10-05T10:01:00Z', 0, time + 20000), 40);
});

test('lost acknowledgement is reconciled without overwriting a different local answer', () => {
    const pending = { q1: { response: { options: ['B'] }, version: 0 }, q2: { response: { text: 'Local' }, version: 0 } };
    const reconciled = reconcileAnswers(pending, [{ item_key: 'q1', response: { options: ['B'] }, version: 1 }, { item_key: 'q2', response: { text: 'Remote' }, version: 1 }]);
    assert.equal(reconciled.q1, undefined);
    assert.equal(reconciled.q2.response.text, 'Local');
    assert.equal(pending.q1.version, 0);
});

test('recording filenames match the browser audio container', () => {
    assert.equal(recordingFilename('audio/mp4'), 'recording.mp4');
    assert.equal(recordingFilename('audio/ogg;codecs=opus'), 'recording.ogg');
    assert.equal(recordingFilename('audio/webm;codecs=opus'), 'recording.webm');
});
