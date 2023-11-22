# docs

The docs are build using [Sphinx](https://github.com/sphinx-doc/sphinx).
To build the documentation locally, you need a installation of Sphinx,
for example using [pipx](https://pypa.github.io/pipx/)

```sh
pipx install sphinx
pipx inject sphinx recommonmark
pipx inject sphinx sphinx_rtd_theme
```

Then run

```sh
make html
```

the rendered output will appear in `build`.
