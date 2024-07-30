function translateItem(name) {
  if (this.$tc("menu." + name).search("menu") !== -1) {
    return name
  } else {
    return this.$tc("menu." + name)
  }
}

export { translateItem }
