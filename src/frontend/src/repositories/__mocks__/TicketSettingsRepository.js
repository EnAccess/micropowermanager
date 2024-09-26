const ticketSettingsListResponse = require("./responses/settings/ticketSettingsList.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(ticketSettingsListResponse))
    })
  },
  update() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(ticketSettingsListResponse))
    })
  },
}
