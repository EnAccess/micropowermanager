import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { EventBus } from "@/shared/eventbus"
import CustomerRepository from "../repositories/CustomerRepository"

export class CustomerService {
  constructor() {
    this.repository = CustomerRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/steama-meters/steama-customer"
    this.routeName = "/steama-meters/steama-customer"
    this.customer = {
      id: null,
      steamaId: null,
      firstName: null,
      lastName: null,
      telephone: null,
      energyPrice: null,
      siteId: null,
      siteName: null,
      lowBalanceWarning: null,
    }
  }

  fromJson(customerData) {
    this.customer = {
      id: customerData.id,
      steamaId: customerData.customer_id,
      firstName: customerData.mpm_person.name,
      lastName: customerData.mpm_person.surname,
      telephone: customerData.mpm_person.addresses[0].phone,
      siteId: customerData.site.mpm_mini_grid.id,
      siteName: customerData.site.mpm_mini_grid.name,
      energyPrice: customerData.energy_price,
      lowBalanceWarning: customerData.low_balance_warning,
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
        return response.data.data.result
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

  async updateCustomer(customer) {
    try {
      let customerPM = {
        id: customer.id,
        steama_id: customer.steamaId,
        low_balance_warning: customer.lowBalanceWarning,
        energy_price: customer.energyPrice,
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

  async getCustomerName(customerId) {
    try {
      let response = await this.repository.get(customerId)
      if (response.status === 200) {
        return response.data.data.name
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  search(term) {
    this.pagingUrl = "/api/steama-meters/steama-customer/advanced/search"
    EventBus.$emit("loadPage", this.pagingUrl, { term: term })
  }

  showAll() {
    this.pagingUrl = "/api/steama-meters/steama-customer"
    EventBus.$emit("loadPage", this.pagingUrl, {})
  }
}
