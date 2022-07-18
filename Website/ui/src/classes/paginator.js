import { baseUrl } from '@/repositories/Client/AxiosClient';
export class Paginator {

    constructor(url) {
        this.url = `${baseUrl}${url}`
        this._initialize()
        this.postData = null

    }

    _initialize () {
        this.currentPage = 0
        this.totalPage = 0
        this.from = 0
        this.to = 0
        this.totalEntries = 0
        this.perPage = 15
    }

    setPaginationBaseUrl (url) {
        this.url = url
    }

    setPostData (data) {
        this.postData = data
    }

    nextPage () {
        if (this.currentPage < this.totalPage)
            this.currentPage++
    }

    prevPage () {
        if (this.currentPage > 1)
            this.currentPage--
    }

    loadPage (page, param = {}) {

        param['page'] = page
        param['per_page'] = this.perPage
        return axios.get(this.url, {
            params: param
        }
        ).then(response => {
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
