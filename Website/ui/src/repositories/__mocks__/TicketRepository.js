const ticketCreateResponse = require("./responses/ticket/ticketCreate.json")

export default {
  create() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(ticketCreateResponse))
    })
  },
}
