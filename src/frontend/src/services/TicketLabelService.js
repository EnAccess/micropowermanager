import { ErrorHandler } from "@/Helpers/ErrorHandler"
import TicketLabelRepository from "@/repositories/TicketLabelRepository"

export class TicketLabelService {
  constructor() {
    this.repository = TicketLabelRepository
    this.list = []
    this.colors = {
      nocolor: "null",
      blue: "#5470c6",
      lime: "#91cc75",
      yellow: "#fac858",
      red: "#ee6666",
      sky: "#73c0de",
      green: "#3ba272",
      purple: "#9a60b4",
      orange: "#fc8452",
      pink: "#ea7ccc",
    }
    this.newLabelName = ""
    this.currentColor = null
    this.outSourcing = false
  }

  async getLabels() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.list = response.data.data
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      console.log(e)
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async createLabel(name, color, outsourcing) {
    try {
      let labelPM = {
        labelName: name,
        labelColor: color,
        outSourcing: outsourcing,
      }
      let response = await this.repository.create(labelPM)
      if (response.status === 201 || response.status === 200) {
        let labelData = response.data.data
        this.list.push(labelData)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  resetLabel() {
    this.newLabelName = ""
    this.currentColor = null
    this.outSourcing = false
  }
}
