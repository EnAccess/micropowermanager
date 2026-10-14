import Client from "@/repositories/Client/AxiosClient.js"

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

    return Client.get(this.resource, {
      params: localParam,
    }).then((response) => {
      const data = response.data
      // Laravel resource collections nest the pagination fields under "meta"
      const pagination = data.meta ?? data
      this.from = pagination.from
      this.to = pagination.to
      this.totalPage = pagination.last_page
      this.currentPage = pagination.current_page
      this.totalEntries = pagination.total

      return data
    })
  }
}
