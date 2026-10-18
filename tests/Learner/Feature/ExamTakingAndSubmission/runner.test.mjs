import test from 'node:test';
import assert from 'node:assert/strict';
import { orderedOptions, canNavigateQuestion } from '../../../../resources/js/exam-runner.js';

test('option order survives canonicalized object keys without changing answer identity', () => {
    assert.deepEqual(orderedOptions({ options: { A: 'One', B: 'Two', C: 'Three' }, option_order: ['C', 'A', 'B'] }), [['C', 'Three'], ['A', 'One'], ['B', 'Two']]);
});
test('locked and linear navigation reject previous and skipped questions', () => {
    assert.equal(canNavigateQuestion(0, 1, { lock_previous: true }), false);
    assert.equal(canNavigateQuestion(3, 1, { navigation: 'linear' }), false);
    assert.equal(canNavigateQuestion(2, 1, { lock_previous: true, navigation: 'linear' }), true);
    assert.equal(canNavigateQuestion(0, 1, { navigation: 'free' }), true);
});
