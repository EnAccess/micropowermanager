import { ErrorHandler } from "@/Helpers/ErrorHandler"
import i18n from "../i18n"
import PaymentHistoryRepository from "@/repositories/PaymentHistoryRepository"

export class PaymentService {
  constructor() {
    this.repository = PaymentHistoryRepository
    this.paymentDetailData = []
    this.chartData = [[i18n.tc("words.month"), i18n.tc("words.sale")]]
    this.flow = []
    this.monthNames = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "June",
      "July",
      "Aug",
      "Sept",
      "Oct",
      "Nov",
      "Dec",
    ]
  }

  async getPaymentDetail(personId, period) {
    try {
      let response = await this.repository.getPaymentDetail(personId, period)
      if (response.status === 200) {
        this.fillPaymentDetailChartData(response.data)
        return response.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  async getPaymentFlow(personId) {
    try {
      let response = await this.repository.getFlow(personId)
      if (response.status === 200) {
        this.fillPaymentFlowChartData(response.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  async getPeriod(personId) {
    try {
      let response = await this.repository.getPeriod(personId)
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  async getDebt(personId) {
    try {
      let response = await this.repository.getDebt(personId)
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  fillPaymentFlowChartData(paymentFlowData) {
    this.flow = []
    for (const paymentFlowDataKey in paymentFlowData) {
      this.flow.push(parseInt(paymentFlowData[paymentFlowDataKey]))
      this.chartData.push([
        this.monthNames[paymentFlowDataKey],
        parseInt(paymentFlowData[paymentFlowDataKey]),
      ])
    }
    return this.chartData
  }

  fillPaymentDetailChartData(paymentDetail) {
    this.paymentDetailData = [
      [
        i18n.tc("words.period"),
        i18n.tc("words.energy"),
        i18n.tc("phrases.accessRate"),
        i18n.tc("phrases.loanRate"),
        i18n.tc("phrases.downPayment"),
      ],
    ]
    for (let i in paymentDetail) {
      let chartDataItem = [
        i,
        "energy" in paymentDetail[i] ? parseInt(paymentDetail[i]["energy"]) : 0,
        "access rate" in paymentDetail[i]
          ? parseInt(paymentDetail[i]["access rate"])
          : 0,
        "installment" in paymentDetail[i]
          ? parseInt(paymentDetail[i]["installment"])
          : 0,
        "down payment" in paymentDetail[i]
          ? parseInt(paymentDetail[i]["down payment"])
          : 0,
      ]
      this.paymentDetailData.push(chartDataItem)
    }
    return this.paymentDetailData
  }
}
