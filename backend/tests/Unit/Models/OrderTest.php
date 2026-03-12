<?php

use App\Models\Order;

describe('Order Model', function () {
    it('has all required status constants', function () {
        expect(Order::ALL_STATUSES)->toContain(
            'open', 'accepted', 'rejected', 'preparing', 'done', 'delivered'
        );
    });

    it('defines valid transitions for each status', function () {
        expect(Order::VALID_TRANSITIONS)->toHaveKeys([
            'open', 'accepted', 'preparing', 'done',
        ]);
    });

    it('allows open → accepted transition', function () {
        expect(Order::VALID_TRANSITIONS['open'])->toContain('accepted');
    });

    it('allows open → rejected transition', function () {
        expect(Order::VALID_TRANSITIONS['open'])->toContain('rejected');
    });

    it('does not allow open → delivered transition', function () {
        expect(Order::VALID_TRANSITIONS['open'])->not->toContain('delivered');
    });

    it('allows done → delivered as final transition', function () {
        expect(Order::VALID_TRANSITIONS['done'])->toContain('delivered');
    });

    it('does not have transitions from delivered', function () {
        expect(Order::VALID_TRANSITIONS)->not->toHaveKey('delivered');
    });

    it('does not have transitions from rejected', function () {
        expect(Order::VALID_TRANSITIONS)->not->toHaveKey('rejected');
    });
});