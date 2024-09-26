const batchRevenueResponse = require("./responses/revenue/batchRevenue.json")

export default {
  getRevenueForPeriod() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(batchRevenueResponse))
    })
  },
}
