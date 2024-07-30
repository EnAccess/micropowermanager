const agentCreateResponse = require("./responses/agent/agentCreate.json")
const agentUpdateResponse = require("./responses/agent/agentUpdate.json")
const agentDetailsResponse = require("./responses/agent/agentDetails.json")

export default {
  create() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(agentCreateResponse))
    })
  },
  update() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(agentUpdateResponse))
    })
  },
  detail() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(agentDetailsResponse))
    })
  },
}
