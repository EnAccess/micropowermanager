const transactionListResponse = require("./responses/transaction/transactionList.json")
const transactionDetailResponse = require("./responses/transaction/transactionDetail.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(transactionListResponse))
    })
  },
  get() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(transactionDetailResponse))
    })
  },
}
