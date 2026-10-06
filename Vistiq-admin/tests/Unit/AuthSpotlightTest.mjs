import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createBeamGeometry, beamClipPath } from '../../resources/js/auth-spotlight.js';

test('a beam pointing left excludes text behind the lamp', () => {
    const beam = createBeamGeometry({ x: 100, y: 50 }, { x: 0, y: 50 }, 200);

    assert.equal(beam.angle, Math.PI);
    assert.ok(beam.points[1].x < 0 && beam.points[2].x < 0);
    assert.ok(beam.points[1].y > 50 && beam.points[2].y < 50);
});

test('the cone opens 30 degrees while following a vertical pointer', () => {
    const beam = createBeamGeometry({ x: 0, y: 0 }, { x: 0, y: 50 }, 100);
    const first = Math.atan2(beam.points[1].y, beam.points[1].x);
    const second = Math.atan2(beam.points[2].y, beam.points[2].x);

    assert.ok(Math.abs((second - first) * 180 / Math.PI - 30) < 0.000001);
    assert.ok(Math.abs(beam.end.x) < 0.000001);
    assert.equal(beam.end.y, 100);
});

test('text clipping projects the same cone into the input viewport', () => {
    const points = [{ x: 200, y: 100 }, { x: 50, y: 50 }, { x: 50, y: 150 }];

    assert.equal(beamClipPath(points, { left: 100, top: 80 }), 'polygon(100px 20px, -50px -30px, -50px 70px)');
    assert.equal(beamClipPath(points, { left: 100, top: 180 }), 'polygon(100px -80px, -50px -130px, -50px -30px)');
});

test('a pointer on the source produces a finite leftward beam', () => {
    const beam = createBeamGeometry({ x: 12, y: 12 }, { x: 12, y: 12 }, 100);

    assert.equal(beam.angle, Math.PI);
    assert.ok(beam.points.every((point) => Number.isFinite(point.x) && Number.isFinite(point.y)));
});
