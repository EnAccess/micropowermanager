import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MeterModelRepository from "../repositories/MeterModelRepository"

export class MeterModelService {
  constructor() {
    this.repository = MeterModelRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/spark-meters/sm-meter-model"
    this.routeName = "/spark-meters/sm-meter-model"
    this.meterModel = {
      id: null,
      modelName: null,
      continuousLimit: null,
      inrushLimit: null,
      siteId: null,
    }
  }
  fromJson(meterModelData) {
    this.meterModel = {
      id: meterModelData.id,
      modelName: meterModelData.model_name,
      continuousLimit: meterModelData.continuous_limit,
      inrushLimit: meterModelData.inrush_limit,
      siteName: meterModelData.site.mpm_mini_grid.name,
    }
    return this.meterModel
  }
  updateList(data) {
    this.list = []
    for (let m in data) {
      let meterModel = this.fromJson(data[m])
      this.list.push(meterModel)
    }
  }

  async getMeterModels() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async syncMeterModels() {
    try {
      let response = await this.repository.sync()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async checkMeterModels() {
    try {
      let response = await this.repository.syncCheck()
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async getMeterModelsCount() {
    try {
      let response = await this.repository.count()
      if (response.status === 200) {
        this.count = response.data
        return this.count
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
