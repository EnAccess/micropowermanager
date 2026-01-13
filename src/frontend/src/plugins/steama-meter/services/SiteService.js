import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SiteRepository from "../repositories/SiteRepository"

export class SiteService {
  constructor() {
    this.repository = SiteRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/steama-meters/steama-site"
    this.routeName = "/steama-meters/steama-site"
    this.site = {
      id: null,
      name: null,
      latitude: null,
      longitude: null,
    }
  }

  fromJson(siteData) {
    this.site = {
      id: siteData.id,
      name: siteData.mpm_mini_grid.name,
      latitude: siteData.mpm_mini_grid.location.points.split(",")[0],
      longitude: siteData.mpm_mini_grid.location.points.split(",")[1],
    }
    return this.site
  }

  updateList(data) {
    this.list = []
    for (let s in data) {
      let site = this.fromJson(data[s])
      this.list.push(site)
    }
  }

  async syncSites() {
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

  async checkSites() {
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

  async getSitesCount() {
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

  async checkLocation() {
    try {
      let response = await this.repository.location()
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
}
