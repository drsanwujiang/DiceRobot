"""内置插件。

每个模块导出一个名为 ``plugin`` 的 :class:`~dicerobot.bot.plugin.Plugin` 对象，由
:func:`~dicerobot.bot.loader.load_registry` 按显式清单加载。第三方插件是独立的
分发包，通过 entry points 发现，不放在此处。
"""
