import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { EventBus } from "@/shared/eventbus"
import CustomerRepository from "../repositories/CustomerRepository"

export class CustomerService {
  constructor() {
    this.repository = CustomerRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/spark-meters/sm-customer"
    this.routeName = "/spark-meters/sm-customer"
    this.customer = {
      id: null,
      name: null,
      sparkId: null,
      siteName: null,
      creditBalance: null,
      LowBalanceLimit: null,
    }
  }

  fromJson(customerData) {
    this.customer = {
      id: customerData.id,
      name: customerData.mpm_person.name,
      sparkId: customerData.customer_id,
      siteName: customerData.site.mpm_mini_grid.name,
      creditBalance: customerData.credit_balance,
      lowBalanceLimit: customerData.low_balance_limit,
    }
    return this.customer
  }

  updateList(data) {
    this.list = []
    for (let c in data) {
      let customer = this.fromJson(data[c])
      this.list.push(customer)
    }
  }

  async getCustomers() {
    try {
      let response = await this.repository.list()
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

  async syncCustomers() {
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

  async checkCustomers() {
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

  async getCustomersCount() {
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

  async checkConnectionTypes() {
    try {
      let response = await this.repository.connections()
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

  async updateCustomer(customer) {
    try {
      let customerPM = {
        id: customer.id,
        low_balance_limit: customer.lowBalanceLimit,
      }
      let response = await this.repository.update(customerPM)
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

  search(term) {
    this.pagingUrl = "/api/spark-meters/sm-customer/search"
    EventBus.$emit("loadPage", this.pagingUrl, { term: term })
  }

  showAll() {
    this.pagingUrl = "/api/spark-meters/sm-customer"
    EventBus.$emit("loadPage", this.pagingUrl, {})
  }
}
