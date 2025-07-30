import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { resources } from "@/resources"
import { baseUrl } from "@/repositories/Client/AxiosClient"
import ManufacturerRepository from "@/repositories/ManufacturerRepository"

export class Manufacturer {
  constructor(id = 0, name = "") {
    this.id = id
    this.manufacturerName = name
  }

  fromJson(jsonData) {
    this.id = jsonData.id
    this.manufacturerName = jsonData.name
    this.webSite = jsonData.website
    return this
  }
}

export class Manufacturers {
  constructor() {
    this.list = []
  }

  async getList() {
    return await axios
      .get(`${baseUrl}${resources.manufacturer.list}`)
      .then((response) => {
        let data = response.data.data
        for (let m in data) {
          let manufacturer = new Manufacturer()
          this.list.push(manufacturer.fromJson(data[m]))
        }
        return this.list
      })
  }

  findById() {
    this.list.find(function (m) {
      return m.id == 2
    })

    return new Manufacturer(1, "Hebele")
  }
}

export class ManufacturerService {
  constructor() {
    this.repository = ManufacturerRepository
    this.list = []
  }

  async getManufacturers() {
    try {
      const { data, status, error } = await this.repository.list()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.list = data.data

      return this.list
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
