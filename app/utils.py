from typing import Any
import asyncio

__all__ = [
    "deep_update",
    "run_command",
    "run_command_wait"
]


def deep_update(base: dict[str, Any], *updates: dict[str, Any]) -> dict[str, Any]:
    result = base.copy()

    for update in updates:
        for key, value in update.items():
            if key in result and isinstance(result[key], dict) and isinstance(value, dict):
                result[key] = deep_update(result[key], value)
            else:
                result[key] = value

    return result


async def run_command(command: str) -> asyncio.subprocess.Process:
    return await asyncio.create_subprocess_shell(
        command,
        stdout=asyncio.subprocess.DEVNULL,
        stderr=asyncio.subprocess.DEVNULL
    )


async def run_command_wait(command: str) -> int:
    return await (await run_command(command)).wait()
