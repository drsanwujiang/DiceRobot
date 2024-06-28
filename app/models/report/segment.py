from typing import Any, Literal

from ...enum import SegmentType
from ...models import BaseModel

__all__ = [
    "Segment",
    "Text",
    "Image",
    "At"
]


class Segment(BaseModel):
    type: SegmentType
    data: Any

    def model_dump(self, **kwargs) -> dict[str, Any]:
        return super().model_dump(exclude_none=True, **kwargs)

    def model_dump_json(self, **kwargs) -> str:
        return super().model_dump_json(exclude_none=True, **kwargs)


class Text(Segment):
    class Data(BaseModel):
        text: str

    type: Literal[SegmentType.TEXT] = SegmentType.TEXT
    data: Data


class Image(Segment):
    class Data(BaseModel):
        file: str
        type: str = None
        url: str = None

    type: Literal[SegmentType.IMAGE] = SegmentType.IMAGE
    data: Data


class At(Segment):
    class Data(BaseModel):
        qq: int

    type: Literal[SegmentType.AT] = SegmentType.AT
    data: Data
