import { Paginator } from "@/Helpers/Paginator"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils"
import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { EventBus } from "@/shared/eventbus"
import SolarHomeSystemRepository from "@/repositories/SolarHomeSystemRepository"

export class Transactions {
  constructor(shsId) {
    this.tokens = []
    this.paginator = new Paginator(
      resources.solarHomeSystems.transactions + shsId + "/transactions",
      {
        perPage: 15,
        showPerPage: true,
        subscriber: "shs.transactions",
      },
    )
  }

  updateList(data) {
    this.tokens = []
    for (let t in data) {
      this.tokens.push(data[t])
    }
  }
}

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
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  search(term) {
    this.paginator = new Paginator(`${this.repository.resource}/search`)
    EventBus.$emit("loadPage", this.paginator, { term: term })
  }
  async getSolarHomeSystem(id) {
    try {
      const response = await this.repository.detail(id)
      if (response && response.data && response.data.data) {
        return convertObjectKeysToCamelCase(response.data.data)
      }
      return null
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  showAll() {
    this.paginator = new Paginator(this.repository.resource)
    EventBus.$emit("loadPage", this.paginator)
  }

  async getTransactions(id) {
    try {
      const response = await this.repository.transactions(id)
      if (response && response.data && response.data.data) {
        return convertObjectKeysToCamelCase(response.data.data)
      }
      return null
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
