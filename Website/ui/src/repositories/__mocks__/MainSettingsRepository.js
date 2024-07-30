const mainSettingsListResponse = require("./responses/settings/mainSettingsList.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(mainSettingsListResponse))
    })
  },
  update(id, data) {
    return new Promise((resolve) => {
      process.nextTick(() => {
        if (mainSettingsListResponse.data.data[0].id === id) {
          mainSettingsListResponse.data.data[0] = data
          resolve(mainSettingsListResponse)
        }
      })
    })
  },
}
