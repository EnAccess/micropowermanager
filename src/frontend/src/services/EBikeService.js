import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils.js"
import EBikeRepository from "@/repositories/EBikeRepository.js"
import { EventBus } from "@/shared/eventbus.js"

export class EBikeService {
  constructor() {
    this.repository = EBikeRepository
    this.paginator = new Paginator(this.repository.resource)
    this.list = []
    this.eBike = {
      serialNumber: null,
      applianceId: null,
      manufacturerId: null,
      receiveTime: null,
      lat: null,
      lng: null,
      speed: null,
      mileage: null,
      status: null,
      soh: null,
      batteryLevel: null,
      batteryVoltage: null,
      statusOn: null,
    }
  }

  updateList(data) {
    this.list = data.map((eBike) => convertObjectKeysToCamelCase(eBike))
  }

  async createEBike() {
    try {
      const eBike = convertObjectKeysToSnakeCase(this.eBike)
      const { data, status, error } = await this.repository.create(eBike)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getEBikeBySerialNumber(serialNumber) {
    try {
      const { data, status, error } = await this.repository.detail(serialNumber)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.eBike = convertObjectKeysToCamelCase(data.data)
      this.eBike.statusOn =
        this.eBike.status && this.eBike.status.includes("ACCON")
      return this.eBike
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async switchEBike(postData) {
    try {
      const { data, status, error } = await this.repository.switch(
        convertObjectKeysToSnakeCase(postData),
      )
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  search(term) {
    this.paginator = new Paginator(`${this.repository.resource}/search`)
    EventBus.$emit("loadPage", this.paginator, { term: term })
  }

  showAll() {
    this.paginator = new Paginator(this.repository.resource)
    EventBus.$emit("loadPage", this.paginator)
  }
}
