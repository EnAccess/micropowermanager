import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"
import SubConnectionTypeRepository from "@/repositories/SubConnectionTypeRepository"

export class SubConnectionTypeService {
  constructor() {
    this.repository = SubConnectionTypeRepository
    this.subConnectionTypes = []
    this.target = {
      newConnection: 0,
      totalRevenue: 0,
      connectedPower: 0,
      energyPerMonth: 0,
      averageRevenuePerMonth: 0,
    }
    this.subConnectionType = {
      id: null,
      name: null,
      target: this.target,
    }
    this.paginator = new Paginator(resources.connections.sublist)
  }

  async getSubConnectionTypes(connectionTypeId) {
    try {
      let response = await this.repository.index(connectionTypeId)
      if (response.status === 200) {
        this.subConnectionTypes = response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }

  async createSubConnectionType(subConnectionType) {
    try {
      let subConnectionType_PM = {
        name: subConnectionType.name,
        connection_type_id: subConnectionType.connection_type_id,
        tariff_id: subConnectionType.tariff_id,
      }
      let response = await this.repository.store(subConnectionType_PM)
      if (response.status === 201) {
        return this.getSubConnectionTypes(
          subConnectionType_PM.connection_type_id,
        )
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }

  async updateSubConnectionType(subConnectionType) {
    try {
      let response = await this.repository.update(subConnectionType)
      if (response.status === 200) {
        const updatedSubConnectionType = response.data.data
        this.subConnectionTypes.map((s) => {
          if (s.id === updatedSubConnectionType.id) {
            s.tariff = updatedSubConnectionType.tariff
          }
        })
        return subConnectionType
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
}
