const targetCreateResponse = require("./responses/target/targetCreate.json")

export default {
  store() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(targetCreateResponse))
    })
  },
}
