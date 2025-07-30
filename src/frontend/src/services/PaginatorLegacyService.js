import { ErrorHandler } from "@/Helpers/ErrorHandler"
import PaginatorLegacyRepository from "@/repositories/PaginatorLegacyRepository"

export class PaginatorLegacyService {
  constructor(url) {
    this.repository = PaginatorLegacyRepository
    this.paginator = {
      url: url,
      method: "GET",
      currentPage: 0,
      totalPage: 0,
      from: 0,
      to: 0,
      totalEntries: 0,
      perPage: 15,
      postData: null,
      data: [],
    }
  }

  setPostData(data) {
    this.paginator.postData = data
  }

  nextPage() {
    if (this.paginator.currentPage < this.paginator.totalPage)
      this.paginator.currentPage++
  }

  prevPage() {
    if (this.paginator.currentPage > 1) this.paginator.currentPage--
  }

  fromJson(data) {
    // A different version of the service used to access the data
    // via the `meta` key.
    // Not sure what is correct here
    // this.paginator.from = data.meta.from
    // this.paginator.to = data.meta.to
    // this.paginator.totalPage = data.meta.last_page
    // this.paginator.currentPage = data.meta.current_page
    // this.paginator.totalEntries = data.meta.total
    this.paginator.from = data.from
    this.paginator.to = data.to
    this.paginator.totalPage = data.last_page
    this.paginator.currentPage = data.current_page
    this.paginator.totalEntries = data.total
    this.paginator.data = data.data
    return this.paginator
  }

  async loadPage(page, param = {}) {
    param["page"] = page
    param["per_page"] = this.paginator.perPage
    try {
      let response = await this.repository.get(this.paginator.url, param)

      if (response.status === 200) {
        let data = response.data
        return this.fromJson(data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
