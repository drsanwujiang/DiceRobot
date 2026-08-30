"""事件去重的测试。"""

from __future__ import annotations

from datetime import UTC, datetime, timedelta

import pytest

from dicerobot.bot.dedup import EventDeduplicator


class FakeClock:
    def __init__(self) -> None:
        self.now = datetime(2026, 1, 1, tzinfo=UTC)

    def __call__(self) -> datetime:
        return self.now

    def advance(self, **kwargs: float) -> None:
        self.now += timedelta(**kwargs)


@pytest.fixture
def clock() -> FakeClock:
    return FakeClock()


def test_first_sighting_is_accepted(clock: FakeClock) -> None:
    deduplicator = EventDeduplicator(now=clock)

    assert deduplicator.is_new("EVENT_1") is True


def test_repeat_within_ttl_is_rejected(clock: FakeClock) -> None:
    """平台在未及时收到确认时会重推，此处将其拦下。"""

    deduplicator = EventDeduplicator(ttl=timedelta(minutes=10), now=clock)
    deduplicator.is_new("EVENT_1")

    clock.advance(minutes=9)

    assert deduplicator.is_new("EVENT_1") is False


def test_record_expires_after_ttl(clock: FakeClock) -> None:
    deduplicator = EventDeduplicator(ttl=timedelta(minutes=10), now=clock)
    deduplicator.is_new("EVENT_1")

    clock.advance(minutes=10, seconds=1)

    assert deduplicator.is_new("EVENT_1") is True
    assert len(deduplicator) == 1


def test_distinct_events_do_not_interfere(clock: FakeClock) -> None:
    deduplicator = EventDeduplicator(now=clock)

    assert deduplicator.is_new("EVENT_1") is True
    assert deduplicator.is_new("EVENT_2") is True


def test_oldest_records_are_evicted_beyond_capacity(clock: FakeClock) -> None:
    """事件量突增时按容量淘汰，约束内存占用。"""

    deduplicator = EventDeduplicator(max_size=3, now=clock)

    for index in range(5):
        deduplicator.is_new(f"EVENT_{index}")

    assert len(deduplicator) == 3
    assert deduplicator.is_new("EVENT_0") is True
    assert deduplicator.is_new("EVENT_4") is False
