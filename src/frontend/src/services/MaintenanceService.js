import { ErrorHandler } from "@/Helpers/ErrorHandler"
import moment from "moment"

import MaintenanceRepository from "@/repositories/MaintenanceRepository"

export class MaintenanceService {
  constructor() {
    this.repository = MaintenanceRepository
    this.employees = []
    this.maintenanceData = {
      creator: null,
      maintenance: true,
      title: null,
      assigned: null,
      category: null,
      amount: null,
      description: null,
      dueDate: null,
    }
    this.personData = {
      customer_type: "maintenance",
      name: null,
      surname: null,
      phone: null,
      city_id: null,
      mini_grid_id: null,
      sex: "male",
    }
  }

  async getEmployees() {
    try {
      let response = await this.repository.list()
      if (response.status === 200 || response.status === 201) {
        this.employees = response.data.data
        return this.employees
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async createMaintenance(personalData) {
    try {
      let response = await this.repository.create(personalData)
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

  setDueDate(date) {
    let formattedDate = moment(date)
    this.maintenanceData.dueDate = formattedDate.format("YYYY-MM-DD")
  }

  resetMaintenance() {
    this.maintenanceData = {
      creator: null,
      maintenance: true,
      title: null,
      assigned: null,
      category: null,
      amount: null,
      description: null,
      dueDate: null,
    }
  }
  resetPersonData() {
    this.personData = {
      customer_type: "maintenance",
      name: null,
      surname: null,
      phone: null,
      city_id: null,
      mini_grid_id: null,
      sex: "male",
    }
  }
}
