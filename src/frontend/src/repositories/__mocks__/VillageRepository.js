const villageCreateResponse = require("./responses/village/villageCreate.json")
const villageListResponse = require("./responses/village/villageList.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(villageListResponse))
    })
  },
  create() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(villageCreateResponse))
    })
  },
}
