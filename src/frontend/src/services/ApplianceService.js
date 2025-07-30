import { Paginator } from "@/Helpers/Paginator"
import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils"
import ApplianceRepository from "@/repositories/ApplianceRepository"

export class ApplianceService {
  constructor() {
    this.repository = ApplianceRepository
    this.list = []
    this.appliance = {
      id: null,
      name: null,
      edit: false,
      assetTypeId: null,
      assetTypeName: null,
      price: null,
      downPayment: null,
      rate: null,
      rateType: "monthly",
      rateCost: null,
    }
    this.paginator = new Paginator(resources.assets.list)
  }

  fromJson(data) {
    this.appliance = {
      id: data.id,
      name: data.name,
      edit: false,
      assetTypeId: data.asset_type_id,
      assetTypeName: data.asset_type.name,
      price: data.price,
    }
  }

  async updateAppliance(appliance) {
    try {
      const { data, status, error } = await this.repository.update(appliance)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async deleteAppliance(appliance) {
    try {
      const { data, status, error } = await this.repository.delete(appliance.id)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  updateList(data) {
    this.list = []
    this.list = data.map((asset) => {
      return {
        id: asset.id,
        name: asset.name,
        price: asset.price,
        assetTypeId: asset.asset_type.id,
        assetTypeName: asset.asset_type.name,
        updatedAt: asset.updated_at
          .toString()
          .replace(/T/, " ")
          .replace(/\..+/, ""),
        edit: false,
      }
    })
    return this.list
  }

  async createAppliance() {
    try {
      const appliance = convertObjectKeysToSnakeCase(this.appliance)
      const { data, status, error } = await this.repository.create(appliance)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getAppliances() {
    try {
      this.list = []
      const { data, status, error } = await this.repository.list()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.list = this.updateList(data.data)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
