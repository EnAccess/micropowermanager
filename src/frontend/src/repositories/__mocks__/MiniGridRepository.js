const miniGridCreateResponse = require("./responses/miniGrid/miniGridCreate.json")
const miniGridListResponse = require("./responses/miniGrid/miniGridList.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(miniGridListResponse))
    })
  },
  create() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(miniGridCreateResponse))
    })
  },
}
