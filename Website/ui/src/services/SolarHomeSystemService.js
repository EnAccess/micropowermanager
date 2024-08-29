import { Paginator } from "@/Helpers/Paginator"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils"
import { ErrorHandler } from "@/Helpers/ErrorHander"
import { EventBus } from "@/shared/eventbus"
import SolarHomeSystemRepository from "@/repositories/SolarHomeSystemRepository"

export class SolarHomeSystemService {
  constructor() {
    this.repository = SolarHomeSystemRepository
    this.paginator = new Paginator(this.repository.resource)
    this.list = []
    this.shs = {
      serialNumber: null,
      assetId: null,
      manufacturerId: null,
      personId: null,
    }
  }

  updateList(data) {
    this.list = data.map((shs) => convertObjectKeysToCamelCase(shs))
  }

  async createSolarHomeSystem() {
    try {
      const shs = convertObjectKeysToSnakeCase(this.shs)
      const { data, status, error } = await this.repository.create(shs)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.data.message
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
