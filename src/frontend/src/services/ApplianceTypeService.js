import { EventBus } from "@/shared/eventbus"
import { ErrorHandler } from "@/Helpers/ErrorHandler"

import ApplianceTypeRepository from "@/repositories/ApplianceTypeRepository"

export class ApplianceTypeService {
  constructor() {
    this.repository = ApplianceTypeRepository
    this.list = []
    this.applianceType = {
      id: null,
      name: null,
      updated_at: null,
      edit: false,
    }
  }

  fromJson(data) {
    return {
      id: data.id,
      name: data.name,
      paygoEnabled: data.paygo_enabled,
      updatedAt: data.updated_at
        .toString()
        .replace(/T/, " ")
        .replace(/\..+/, ""),
    }
  }

  updateList(data) {
    this.list = data.map((appliance) => {
      return {
        id: appliance.id,
        name: appliance.name,
        paygoEnabled: appliance.paygo_enabled,
        updated_at: appliance.updated_at
          ? appliance.updated_at
              .toString()
              .replace(/T/, " ")
              .replace(/\..+/, "")
          : "",
        edit: false,
      }
    })
    return this.list
  }

  async createApplianceType() {
    try {
      let response = await this.repository.create(this.applianceType)
      if (response.status === 200 || response.status === 201) {
        this.applianceType.id = response.data.data.id
        this.applianceType.name = response.data.data.name
        this.applianceType.updated_at = response.data.data.updated_at
        EventBus.$emit("applianceTypeAdded", this.applianceType)
        this.resetApplianceType()
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateApplianceType(applianceType) {
    try {
      const response = await this.repository.update(applianceType)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async deleteApplianceType(applianceType) {
    try {
      let response = await this.repository.delete(applianceType.id)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getAppliancesTypes() {
    try {
      this.list = []
      let response = await this.repository.list()
      if (response.status === 200 || response.status === 201) {
        for (const applianceType of response.data.data) {
          this.list.push(this.fromJson(applianceType))
        }
      } else {
        new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  resetApplianceType() {
    this.applianceType = {
      id: null,
      name: null,
      updated_at: null,
      edit: false,
      appliance_type_name: null,
      price: null,
    }
  }
}
