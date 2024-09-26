const miniGridListResponse = require("./responses/miniGrid/miniGridList.json")
const miniGridCreateResponse = require("./responses/miniGrid/miniGridCreate.json")

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
