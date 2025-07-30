import { ErrorHandler } from "@/Helpers/ErrorHandler"
import i18n from "../i18n"

import RevenueRepository from "@/repositories/RevenueRepository"

export class RevenueService {
  constructor() {
    this.repository = RevenueRepository
    this.revenueTrends = null
    this.ticketsData = {}
    this.trendChartData = {
      base: [],
      compare: [],
      overview: [],
    }
    this.openedTicketChartData = []
    this.closedTicketChartData = []
  }

  async getMiniGridRevenueTrends(miniGridId, startDate, endDate, tab) {
    try {
      let period = {
        startDate: startDate,
        endDate: endDate,
      }
      let response = await this.repository.trends(miniGridId, period)

      if (response.status === 200 || response.status === 201) {
        this.revenueTrends = response.data.data
        console.log(this.revenueTrends)
        this.fillRevenueTrendsOverView()
        this.fillRevenueTrends(tab)
        return this.revenueTrends
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  fillRevenueTrendsOverView() {
    this.trendChartData.overview = [[i18n.tc("words.date")]]
    for (let dt in this.revenueTrends) {
      for (let tariffNames in this.revenueTrends[dt]) {
        this.trendChartData.overview[0].push(tariffNames)
      }
      this.trendChartData.overview[0].push(i18n.tc("words.total"))
      break
    }
    for (let x in this.revenueTrends) {
      let tmpChartData = [x]
      let totalRev = 0
      for (let d in this.revenueTrends[x]) {
        tmpChartData.push(this.revenueTrends[x][d].revenue)
        totalRev += this.revenueTrends[x][d].revenue
      }
      tmpChartData.push(totalRev)
      this.trendChartData.overview.push(tmpChartData)
    }
    return this.trendChartData.overview
  }
  fillRevenueTrends(tab) {
    this.trendChartData.base = [[i18n.tc("words.date")]]
    this.trendChartData.compare = [[i18n.tc("words.date")]]

    for (let dt in this.revenueTrends) {
      for (let tariffNames in this.revenueTrends[dt]) {
        this.trendChartData.base[0].push(tariffNames)
        this.trendChartData.compare[0].push(tariffNames)
      }
      this.trendChartData.base[0].push(i18n.tc("words.total"))
      this.trendChartData.compare[0].push(i18n.tc("words.total"))
      if (tab !== "weekly") {
        break
      }
    }

    for (let x in this.revenueTrends) {
      let tmpChartData = [x]
      let totalRev = 0
      for (let d in this.revenueTrends[x]) {
        tmpChartData.push(this.revenueTrends[x][d].revenue)
        totalRev += this.revenueTrends[x][d].revenue
      }
      tmpChartData.push(totalRev)
      this.trendChartData.base.push(tmpChartData)
      this.trendChartData.base.splice(50)
    }
    return this.trendChartData.base
  }
  async getTicketsData(miniGridId) {
    try {
      let response = await this.repository.tickets(miniGridId)
      if (response.status === 200) {
        this.ticketsData = response.data.data
        this.fillTicketChart()
        return this.ticketsData
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  fillTicketChart() {
    let openedTicketChartData = []
    let closedTicketChartData = []

    openedTicketChartData.push([i18n.tc("words.period")])
    closedTicketChartData.push([i18n.tc("words.period")])
    for (let category in this.ticketsData.categories) {
      openedTicketChartData[0].push(
        this.ticketsData.categories[category].label_name,
      )
      openedTicketChartData[0].push({ type: "string", role: "tooltip" })
      closedTicketChartData[0].push(
        this.ticketsData.categories[category].label_name,
      )
      closedTicketChartData[0].push({ type: "string", role: "tooltip" })
    }

    for (let oT in this.ticketsData) {
      if (oT === "categories") {
        continue
      }
      let ticketCategoryData = this.ticketsData[oT]

      let ticketChartDataOpened = [oT]
      let ticketChartDataClosed = [oT]

      for (let tD in ticketCategoryData) {
        let ticketData = ticketCategoryData[tD]
        ticketChartDataOpened.push(
          ticketData.opened,
          oT +
            "\n" +
            [tD] +
            " : " +
            ticketData.opened +
            " " +
            i18n.tc("words.open", 2),
        )
        ticketChartDataClosed.push(
          ticketData.closed,
          oT +
            "\n" +
            [tD] +
            " : " +
            ticketData.closed +
            " " +
            i18n.tc("words.close", 2),
        )
      }

      openedTicketChartData.push(ticketChartDataOpened)
      openedTicketChartData.push(ticketChartDataClosed)
      closedTicketChartData.push(ticketChartDataClosed)
    }

    this.openedTicketChartData = openedTicketChartData
    this.closedTicketChartData = closedTicketChartData
    return this.openedTicketChartData
  }
}
