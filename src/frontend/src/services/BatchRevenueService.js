import { ErrorHandler } from "@/Helpers/ErrorHandler"
import store from "../store/store"
import { resources } from "@/resources"
import BatchRevenueRepository from "@/repositories/BatchRevenueRepository"

export class BatchRevenue {
  constructor() {
    this.revenueList = null
  }

  revenueForPeriod(targetId, targetType, startDate, endDate) {
    return axios
      .post(resources.revenues.batch, {
        target_type_id: targetId,
        target_type: targetType,
        start_date: startDate,
        end_date: endDate,
      })
      .then((response) => {
        return response.data.data
      })
  }

  periodData(period) {
    return this.revenueList[period]
  }
}

export class BatchRevenueService {
  constructor() {
    this.revenueList = null
    this.repository = BatchRevenueRepository
    this.batchRevenues = {}
    this.comparedRevenues = {}
  }

  async getRevenueForPeriod(targetId, targetType, startDate, endDate) {
    let data = {}
    let targetPeriod = {
      target_type_id: targetId,
      target_type: targetType,
      start_date: startDate,
      end_date: endDate,
    }
    try {
      let response = await this.repository.getRevenueForPeriod(targetPeriod)
      if (response.status === 200) {
        data.revenueList = response.data.data

        data.revenueList.averages = this.calculateAverages(data.revenueList)
        return data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
  calculateAverages(list) {
    let data = {}
    for (let connection in list.target.targets) {
      let result = "-"
      if (list.revenue[connection] > 0) {
        result =
          parseInt(list.revenue[connection]) /
          list.total_connections[connection]
      }
      data[connection] = result
    }
    return data
  }
  initializeDonutCharts(initValue, batchRevenues) {
    let donutData = [initValue]
    //donut chart for given period
    let data = batchRevenues.revenueList.revenue
    for (let con in data) {
      let connectionRev = data[con]
      donutData.push([con, parseInt(connectionRev)])
    }
    return donutData
  }
  initializeColumnChart(data) {
    //for revenue target per customer type column chart
    let chartData = [
      [
        { type: "string", label: "Customer Type", role: "domain" },
        { type: "number", label: "Percentage of target", role: "data" },
        { type: "string", role: "tooltip", p: { html: true } },
        { role: "style" },
      ],
    ]
    let targets = data.revenueList.target.targets
    Object.keys(targets).forEach(function (item) {
      let currency = store.getters["settings/getMainSettings"].currency
      let actualRevenue = parseInt(data.revenueList.revenue[item])
      let targetRevenue = targets[item].revenue
      let percentile = 0
      let value = 0
      if (targetRevenue === undefined || targetRevenue === "-") {
        percentile = 0
        targetRevenue = 0
      } else {
        value = parseInt(
          (parseInt(data.revenueList.revenue[item]) * 100) /
            parseInt(targetRevenue),
        )
        if (Number.isNaN(value)) {
          value = 0
          percentile = 0
        } else {
          percentile = value / 100
        }
      }
      // eslint-disable-next-line no-unused-vars
      let color = "#xxxxxx".replace(/x/g, (y) =>
        ((Math.random() * 16) | 0).toString(16),
      )
      let tooltip =
        '<p style="padding: 10px; min-width: 150px;">' +
        '<span style="font-size:x-large; font-weight: bolder; align-content: center">' +
        value.toString() +
        "%</span>" +
        "<br><b>" +
        item +
        "</b>" +
        "<br><b>" +
        "Actual:</b> " +
        actualRevenue
          .toLocaleString("en-US", { maximumFractionDigits: 2 })
          .toString() +
        currency +
        "<br><b>" +
        " Targeted:</b> " +
        targetRevenue
          .toLocaleString("en-US", { maximumFractionDigits: 2 })
          .toString() +
        currency +
        "</p>"
      let chartDataItem = [item, percentile, tooltip, color]
      chartData.push(chartDataItem)
    })
    return chartData
  }
}
