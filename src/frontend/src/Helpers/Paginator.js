import { baseUrl } from "@/repositories/Client/AxiosClient"

export class Paginator {
  constructor(url) {
    //when the consumer passes a fully qualified url use it, otherwise use the base url from axiosClient
    this.url = url.startsWith("http") ? url : `${baseUrl}${url}`
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

  setPaginationBaseUrl(url) {
    this.url = url
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

    return axios
      .get(this.url, {
        params: localParam,
      })
      .then((response) => {
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
