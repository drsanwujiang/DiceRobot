from ...enum import ReportType
from .. import BaseModel


class Report(BaseModel):
    time: int
    self_id: int
    post_type: ReportType
