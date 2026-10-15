import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils.js"
import SolarHomeSystemRepository from "@/repositories/SolarHomeSystemRepository.js"
import { resources } from "@/resources.js"
import { EventBus } from "@/shared/eventbus.js"

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
      serialNumbers: [],
      applianceId: null,
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

  async getOpenPaygoManufacturers() {
    try {
      const { data, status, error } =
        await this.repository.openPaygoManufacturers()
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data.map((manufacturer) =>
        convertObjectKeysToCamelCase(manufacturer),
      )
    } catch (error) {
      const responseData = error.response && error.response.data
      const errorMessage = responseData?.message || error.message

      return new ErrorHandler(errorMessage, "http")
    }
  }

  async registerOpenPaygoDevice(registration) {
    const payload = {
      serial_number: registration.serialNumber,
      manufacturer_id: registration.manufacturerId,
      appliance_id: registration.applianceId,
      person_id: registration.personId,
      secretKeyHex: registration.secretKeyHex,
      startingCode: registration.startingCode,
      nextCounter: registration.nextCounter,
      timeDivider: registration.timeDivider,
    }

    try {
      const { data, status, error } =
        await this.repository.registerOpenPaygoDevice(payload)
      if (status !== 201) return new ErrorHandler(error, "http", status)

      return convertObjectKeysToCamelCase(data.data)
    } catch (error) {
      const responseData = error.response && error.response.data
      const validationErrors = responseData?.errors
      const errorMessage = validationErrors
        ? Object.values(validationErrors).flat().join(" ")
        : responseData?.message || error.message

      return new ErrorHandler(errorMessage, "http")
    }
  }
  async updateSolarHomeSystem(shsId, payload) {
    try {
      const { data, status, error } = await this.repository.update(
        shsId,
        convertObjectKeysToSnakeCase(payload),
      )
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return convertObjectKeysToCamelCase(data.data)
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
