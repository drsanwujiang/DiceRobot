from ...enum import ReportType
from .. import BaseModel

__all__ = [
    "Report"
]


class Report(BaseModel):
    time: int
    self_id: int
    post_type: ReportType
