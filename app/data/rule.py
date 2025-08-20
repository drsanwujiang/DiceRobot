from loguru import logger
import aiofiles

from ..enum import DataType
from ..models.data import RuleSet
from . import DataHandler


class RuleHandler(DataHandler):
    type = DataType.RULE

    async def load_file(self, filepath: str) -> RuleSet | None:
        try:
            async with aiofiles.open(filepath, "r", encoding="utf-8") as f:
                return RuleSet.model_validate_json(await f.read())
        except ValueError:
            logger.exception(f"Failed to load rule file \"{filepath}\"")
            return None
