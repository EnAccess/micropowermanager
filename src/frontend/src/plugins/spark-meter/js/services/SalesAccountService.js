import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SalesAccountRepository from "../repositories/SalesAccountRepository"

export class SalesAccountService {
  constructor() {
    this.repository = SalesAccountRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/spark-meters/sm-sales-account"
    this.routeName = "/spark-meters/sm-sales-account"
    this.salesAccount = {
      id: null,
      siteName: null,
      name: null,
      accountType: null,
      active: null,
      credit: null,
      markup: null,
    }
  }

  fromJson(salesAccountData) {
    this.salesAccount = {
      id: salesAccountData.id,
      name: salesAccountData.name,
      siteName: salesAccountData.site.mpm_mini_grid.name,
      accountType: salesAccountData.account_type,
      active: salesAccountData.active,
      credit: salesAccountData.credit,
      markup: salesAccountData.markup,
    }
    return this.salesAccount
  }

  updateList(data) {
    this.list = []
    for (let a in data) {
      let salesAccount = this.fromJson(data[a])
      this.list.push(salesAccount)
    }
  }

  async syncSalesAccount() {
    try {
      let response = await this.repository.sync()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async checkSalesAccounts() {
    try {
      let response = await this.repository.syncCheck()
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getSalesAccountCount() {
    try {
      let response = await this.repository.count()
      if (response.status === 200) {
        this.count = response.data
        return this.count
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
