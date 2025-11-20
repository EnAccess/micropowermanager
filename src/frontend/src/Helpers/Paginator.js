import Client from "@/repositories/Client/AxiosClient"

export class Paginator {
  constructor(resource) {
    this.resource = resource
    this._initialize()
    this.postData = null
  }

  _initialize() {
    this.currentPage = 0
    this.totalPage = 0
    this.from = 0
    this.to = 0
    this.totalEntries = 0
    this.perPage = 15
  }

  setPaginationResource(resource) {
    this.resource = resource
  }

  setPostData(data) {
    this.postData = data
  }

  nextPage() {
    if (this.currentPage < this.totalPage) this.currentPage++
  }

  prevPage() {
    if (this.currentPage > 1) this.currentPage--
  }

  loadPage(page, param = {}) {
    // take a local, shallow copy of params to prevent
    // unintended route changes
    const localParam = { ...param }

    localParam["page"] = page
    localParam["per_page"] = this.perPage

    return Client.get(this.url, {
      params: localParam,
    }).then((response) => {
      let data = response.data
      this.from = data.from
      this.to = data.to
      this.totalPage = data.last_page
      this.currentPage = data.current_page
      this.totalEntries = data.total

      return data
    })
  }
}
