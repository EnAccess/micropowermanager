const paymentDetailResponse = require("./responses/payment/paymentDetail.json")
const paymentFlowResponse = require("./responses/payment/paymentFlow.json")

export default {
  getFlow() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(paymentFlowResponse))
    })
  },
  getPaymentDetail() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(paymentDetailResponse))
    })
  },
}
