from typing import TYPE_CHECKING
import os
import asyncio
import tarfile
from abc import ABC, abstractmethod
from collections.abc import AsyncGenerator

from loguru import logger
from watchfiles import Change, awatch
import aiofiles
from semver.version import Version
from sse_starlette import JSONServerSentEvent

from ..enum import UpdateStatus

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "Actuator",
    "LogStreamer"
]


class FileWatcher:
    def __init__(self, filepath: str) -> None:
        self.filepath = filepath
        self.queues: list[asyncio.Queue] = []
        self.task: asyncio.Task | None = None

    async def watch_loop(self):
        try:
            async with aiofiles.open(self.filepath, mode="r", encoding="utf-8") as f:
                await f.seek(0, os.SEEK_END)

                async for changes in awatch(self.filepath):
                    for change, path in changes:
                        if change == Change.modified:
                            lines = await f.readlines()

                            if lines:
                                for queue in self.queues:
                                    queue.put_nowait(lines)
        except asyncio.CancelledError:
            logger.debug("File watcher cancelled")

    def start(self):
        logger.debug(f"Start watching file: {self.filepath}")

        self.task = asyncio.create_task(self.watch_loop())

    def stop(self):
        if self.task:
            logger.debug(f"Stop watching file: {self.filepath}")

            self.task.cancel()

    async def subscribe(self) -> asyncio.Queue:
        queue = asyncio.Queue()
        self.queues.append(queue)

        return queue

    def unsubscribe(self, queue: asyncio.Queue):
        if queue in self.queues:
            self.queues.remove(queue)


class LogHelper:
    def __init__(self, logs_dir: str) -> None:
        self.logs_dir = logs_dir
        self.watchers: dict[str, FileWatcher] = {}
        self.lock = asyncio.Lock()

    def check(self, filename: str) -> bool:
        logger.debug(f"Check log file: {filename}")

        if os.path.isfile(os.path.join(self.logs_dir, filename)):
            return True
        elif os.path.isfile(compressed_file := os.path.join(self.logs_dir, f"{filename}.tar.gz")):
            logger.info(f"Decompress log file: {compressed_file}.tar.gz")

            with tarfile.open(compressed_file, "r:gz") as tar:
                tar.extract(filename, self.logs_dir)

            return True

        return False

    async def load(self, filename: str) -> AsyncGenerator[list[str]]:
        logger.debug(f"Load log file: {filename}")

        async with aiofiles.open(os.path.join(self.logs_dir, filename), "r", encoding="utf-8") as f:
            batch = []

            async for line in f:
                batch.append(line)

                if len(batch) >= 100:
                    yield batch
                    batch = []
                    await asyncio.sleep(0.01)

            if batch:
                yield batch

    async def subscribe(self, filename: str) -> asyncio.Queue:
        async with self.lock:
            logger.debug(f"Subscribe to log file: {filename}")

            if filename not in self.watchers:
                self.watchers[filename] = FileWatcher(os.path.join(self.logs_dir, filename))
                self.watchers[filename].start()

            return await self.watchers[filename].subscribe()

    async def unsubscribe(self, filename: str, queue: asyncio.Queue) -> None:
        async with self.lock:
            if filename in self.watchers:
                logger.debug(f"Unsubscribe from log file: {filename}")

                watcher = self.watchers[filename]
                watcher.unsubscribe(queue)

                if len(watcher.queues) == 0:
                    watcher.stop()
                    del self.watchers[filename]

    async def cleanup(self):
        async with self.lock:
            logger.debug("Clean log watchers")

            for filename, watcher in self.watchers.items():
                watcher.stop()

                # Clean temporary files
                if all([
                    os.path.isfile(os.path.join(self.logs_dir, f"{filename}.tar.gz")),
                    os.path.isfile(os.path.join(self.logs_dir, filename))
                ]):
                    os.remove(os.path.join(self.logs_dir, filename))


class Actuator(ABC):
    name: str

    def __init__(self, context: "AppContext", **kwargs) -> None:
        super().__init__(**kwargs)

        self.context = context
        self.update_status = UpdateStatus.NONE

    @property
    @abstractmethod
    def _download_filename(self) -> str:
        ...

    async def initialize(self) -> None:
        ...

    async def cleanup(self) -> None:
        ...

    @abstractmethod
    async def get_version(self) -> str | None:
        ...

    @abstractmethod
    async def _get_latest_version(self) -> Version:
        ...

    async def _check_version(self) -> Version | None:
        current_version = await self.get_version()
        latest_version = await self._get_latest_version()

        if current_version and current_version >= latest_version:
            return None

        return latest_version

    async def _download_file(self, filename: str) -> bool:
        url = f"{self.context.settings.cloud.download.base_url}/{self.name.lower()}/{filename}"

        try:
            async with self.context.http_client.stream("GET", url) as response:
                async with aiofiles.open(f"{self.context.settings.app.dir.temp}/{filename}", "wb") as f:
                    async for chunk in response.aiter_bytes(chunk_size=8192):
                        await f.write(chunk)
        except Exception:
            logger.exception(f"Failed to download {url}")

            return False

        return True

    @abstractmethod
    async def _install(self, filepath: str) -> bool:
        ...

    async def update(self) -> None:
        logger.info(f"Check latest version of {self.name}")

        self.update_status = UpdateStatus.CHECKING

        if (latest_version := await self._check_version()) is None:
            logger.info("No updates available")
            self.update_status = UpdateStatus.COMPLETED
            return

        logger.info(f"Download {self.name}, version: {latest_version}")

        self.update_status = UpdateStatus.DOWNLOADING
        filename = self._download_filename.format(version=latest_version)
        filepath = os.path.join(self.context.settings.app.dir.temp, filename)

        if not await self._download_file(filename):
            try:
                os.remove(filepath)
            except FileNotFoundError:
                pass

            self.update_status = UpdateStatus.FAILED
            return

        logger.info(f"Install {self.name}, version: {latest_version}")

        self.update_status = UpdateStatus.INSTALLING
        install_result = await self._install(filepath)

        try:
            os.remove(filepath)
        except FileNotFoundError:
            pass

        if not install_result:
            self.update_status = UpdateStatus.FAILED
            return

        logger.success(f"Update {self.name} completed")

        self.update_status = UpdateStatus.COMPLETED

    def create_update_stream(self, interval: float = 1.0) -> AsyncGenerator[JSONServerSentEvent]:
        task = asyncio.create_task(self.update())

        async def stream_generator() -> AsyncGenerator[JSONServerSentEvent]:
            while True:
                yield JSONServerSentEvent({"status": self.update_status.value})

                if self.update_status in [UpdateStatus.COMPLETED, UpdateStatus.FAILED]:
                    break
                elif task.done() and self.update_status != UpdateStatus.COMPLETED:
                    self.update_status = UpdateStatus.FAILED
                    continue

                await asyncio.sleep(interval)

            self.update_status = UpdateStatus.NONE

        return stream_generator()


class LogStreamer:
    def __init__(self, logs_dir: str, **kwargs) -> None:
        super().__init__(**kwargs)

        self.log = LogHelper(logs_dir)

    def check_log_file(self, filename: str) -> bool:
        return self.log.check(filename)

    async def create_logs_stream(self, filename: str) -> AsyncGenerator[JSONServerSentEvent]:
        async for batch in self.log.load(filename):
            yield JSONServerSentEvent({"logs": batch})

        queue = await self.log.subscribe(filename)

        try:
            while True:
                yield JSONServerSentEvent({"logs": await queue.get()})
        except asyncio.CancelledError:
            logger.debug("Server-sent event stream cancelled")
        finally:
            await self.log.unsubscribe(filename, queue)

    async def cleanup(self):
        await self.log.cleanup()
