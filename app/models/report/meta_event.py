from typing import Any, Literal

from ...enum import ReportType, MetaEventType, LifecycleMetaEventSubType
from . import Report

__all__ = [
    "MetaEvent",
    "LifecycleMetaEvent",
    "HeartbeatMetaEvent"
]


class MetaEvent(Report):
    post_type: Literal[ReportType.META_EVENT] = ReportType.META_EVENT
    meta_event_type: MetaEventType


class LifecycleMetaEvent(Report):
    meta_event_type: Literal[MetaEventType.LIFECYCLE] = MetaEventType.LIFECYCLE
    sub_type: LifecycleMetaEventSubType


class HeartbeatMetaEvent(Report):
    meta_event_type: Literal[MetaEventType.HEARTBEAT] = MetaEventType.HEARTBEAT
    status: Any
    interval: int
