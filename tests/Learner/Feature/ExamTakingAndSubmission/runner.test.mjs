import test from 'node:test';
import assert from 'node:assert/strict';
import { orderedOptions, canNavigateQuestion, singleFlight } from '../../../../resources/js/exam-runner.js';

test('option order survives canonicalized object keys without changing answer identity', () => {
    assert.deepEqual(orderedOptions({ options: { A: 'One', B: 'Two', C: 'Three' }, option_order: ['C', 'A', 'B'] }), [['C', 'Three'], ['A', 'One'], ['B', 'Two']]);
});
test('locked and linear navigation reject previous and skipped questions', () => {
    assert.equal(canNavigateQuestion(0, 1, { lock_previous: true }), false);
    assert.equal(canNavigateQuestion(3, 1, { navigation: 'linear' }), false);
    assert.equal(canNavigateQuestion(2, 1, { lock_previous: true, navigation: 'linear' }), true);
    assert.equal(canNavigateQuestion(0, 1, { navigation: 'free' }), true);
});

test('submit waits for an in-progress autosave and does not send a duplicate write', async () => {
    let complete, writes = 0;
    const save = singleFlight(() => { writes++; return new Promise(resolve => { complete = resolve; }); });
    const autosave = save();
    await Promise.resolve();
    const submitSave = save();
    assert.equal(writes, 1);
    assert.equal(submitSave, autosave);
    complete('acknowledged');
    assert.equal(await submitSave, 'acknowledged');
});

test('a failed sync releases the flight so the learner can retry', async () => {
    let writes = 0;
    const save = singleFlight(async () => { if (++writes === 1) throw new Error('offline'); return 'saved'; });
    await assert.rejects(save(), /offline/);
    assert.equal(await save(), 'saved');
    assert.equal(writes, 2);
});
