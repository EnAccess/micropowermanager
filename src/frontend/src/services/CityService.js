import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils.js"
import CityRepository from "@/repositories/CityRepository.js"
import { resources } from "@/resources.js"

const CITIES_PER_PAGE = 30

// FIXME: Why is this here? It seems redundant, wrong and circular:
// Cluster.fromJson() -> Cluster.fetchCities() -> City.fromJson() -> City.fetchCluster() -> Cluster.fromJson()
class Cluster {
  constructor() {}

  fromJson(jsonData) {
    this.id = jsonData.id
    this.name = jsonData.name
    this.manager = jsonData.manager
    if ("cities" in jsonData) {
      this.cities = this.fetchCities()
    }
  }

  fetchCities(cities) {
    let result = []
    for (let i in cities) {
      let cityData = cities[i]
      let city = new City()
      city.fromJson(cityData)
      result.push(city)
    }
  }
}

export class Country {
  constructor() {}

  fromJson(jsonData) {
    this.id = jsonData.id
    this.name = jsonData.county_name
    this.countryCode = jsonData.country_code
  }
}

export class City {
  constructor() {}

  fromJson(jsonData) {
    this.id = jsonData.id
    this.name = jsonData.name
    this.countryId = jsonData.country_id
    if ("country" in jsonData) {
      this.country = this.fetchCountry(jsonData.country)
    }
    if ("cluster" in jsonData) {
      this.fetchCluster(jsonData.cluster)
    }
    return this
  }

  fetchCountry(data) {
    let country = new Country()
    country.fromJson(data)
    return country
  }

  fetchCluster(data) {
    let cluster = new Cluster()
    cluster.fromJson(data)
    return cluster
  }
}

export class CityService {
  constructor() {
    this.city = {
      id: 0,
      name: "",
      mini_grid_id: 0,
    }
    this.repository = CityRepository
    this.paginator = new Paginator(resources.city.list)
  }

  async getCities({ page = 1, term = "" } = {}) {
    try {
      const { data, status, error } = await this.repository.list({
        page,
        per_page: CITIES_PER_PAGE,
        term,
      })
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return { cities: data.data, lastPage: data.last_page }
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getCity(cityId) {
    try {
      const { data, status, error } = await this.repository.get(cityId)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async createCity(cityData) {
    try {
      const params = convertObjectKeysToSnakeCase(cityData)
      const { data, status, error } = await this.repository.create(params)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)
      this.city = data.data
      return this.city
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateCity(cityId, cityData) {
    try {
      const params = convertObjectKeysToSnakeCase(cityData)
      const { data, status, error } = await this.repository.update(
        cityId,
        params,
      )
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.city = data.data
      return this.city
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async deleteCity(cityId, options = {}) {
    try {
      const params = convertObjectKeysToSnakeCase(options)
      const { status, error } = await this.repository.delete(cityId, params)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return true
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getLinkedAddresses(cityId) {
    try {
      const { data, status, error } =
        await this.repository.linkedAddresses(cityId)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
