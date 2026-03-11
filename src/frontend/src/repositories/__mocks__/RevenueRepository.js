const revenueTrendResponse = require("./responses/revenue/revenueTrends.json")
const ticketsDataResponse = require("./responses/revenue/ticketsData.json")

export default {
  tickets() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(ticketsDataResponse))
    })
  },
  trends() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(revenueTrendResponse))
    })
  },
}
