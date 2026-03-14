import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils.js"
import VillageRepository from "@/repositories/VillageRepository.js"

export class Country {
  constructor() {}

  fromJson(jsonData) {
    this.id = jsonData.id
    this.name = jsonData.county_name
    this.countryCode = jsonData.country_code
  }
}

export class Village {
  constructor() {}

  fromJson(jsonData) {
    this.id = jsonData.id
    this.name = jsonData.name
    this.countryId = jsonData.country_id
    this.country = "country" in jsonData ? this.fetchCountry(jsonData.country) : null
    this.cluster = "cluster" in jsonData ? jsonData.cluster : null
    return this
  }

  fetchCountry(data) {
    let country = new Country()
    country.fromJson(data)
    return country
  }

}

export class VillageService {
  constructor() {
    this.villages = []
    this.village = {
      id: 0,
      name: "",
      mini_grid_id: 0,
    }
    this.list = []
    this.repository = VillageRepository
  }

  async getVillages() {
    try {
      const { data, status, error } = await this.repository.list()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.villages = data.data
      this.list = data.data

      return this.villages
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async createVillage(villageData) {
    try {
      const params = convertObjectKeysToSnakeCase(villageData)
      const { data, status, error } = await this.repository.create(params)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)
      this.village = data.data
      return this.village
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
