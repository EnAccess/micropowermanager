const mapSettingsResponse = require("./responses/settings/mapSettingsList.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(mapSettingsResponse))
    })
  },
  update() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(mapSettingsResponse))
    })
  },
}
