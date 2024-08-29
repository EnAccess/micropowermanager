jest.mock("../repositories/PaymentHistoryRepository")
import { PaymentService } from "../services/PaymentService"
const paymentService = new PaymentService()

describe("PaymentService #getPaymentFlow", () => {
  it("should get Payment Flow chart data", async () => {
    const data = await paymentService.getPaymentFlow()
    expect(data.length).not.toEqual(0)
  })
})
describe("PaymentService #fillPaymentFlowChartData", () => {
  it("should get right formatted data for payment flow chart", async () => {
    const data = await paymentService.getPaymentFlow() // getPaymentFlow method is call fillPaymentFlowChartData method
    for (let i = 0; i < data.length; i++) {
      expect(data[i]).toEqual(paymentService.chartData[i + 1][1])
    }
  })
})
describe("PaymentService #getPaymentDetail", () => {
  it("should get Payment Overview Chart Data", async () => {
    const data = await paymentService.getPaymentDetail()
    expect(data.length).not.toEqual(0)
  })
})
describe("PaymentService #fillPaymentDetailChartData", () => {
  it("should get right formatted data for payment overview chart", async () => {
    const data = await paymentService.getPaymentDetail() // getPaymentDetail Method is call fillPaymentDetailChartData method
    Object.keys(data).forEach(function (item, index) {
      Object.keys(data[item]).forEach(function (sub) {
        if (sub === "Energy")
          expect(data[item][sub]).toEqual(
            paymentService.paymentDetailData[index + 1][1],
          )
        if (sub === "Access Rate")
          expect(data[item][sub]).toEqual(
            paymentService.paymentDetailData[index + 1][2],
          )
        if (sub === "Loan Rate")
          expect(data[item][sub]).toEqual(
            paymentService.paymentDetailData[index + 1][3],
          )
      })
    })
  })
})
