import { ErrorHandler } from '../Helpers/ErrorHander'
import RepositoryFactory from '../repositories/RepositoryFactory'

export class CertService {
    constructor() {
        this.repository = RepositoryFactory.get('cert')
    }

    async upload(cert) {
        let formData = new FormData()
        if (cert == null) {
            return
        }
        formData.append('cert', cert)
        try {
            const { data } = await this.repository.post(formData, {
                header: { 'Content-Type': 'multipart/form-data' },
            })

            return data.data
        } catch (error) {
            if (error.response) {
                if (error.response.status && error.response.status === 422) {
                    const errorMessage =
                        error.response.data.data.message.cert[0]

                    return new ErrorHandler(errorMessage, 'http', 422)
                }
                const errorMessage = error.response.data.data.message

                return new ErrorHandler(errorMessage, 'http', 400)
            } else {
                const errorMessage = error.message

                return new ErrorHandler(errorMessage, 'http')
            }
        }
    }

    async getUploadedCertName() {
        try {
            const { data } = await this.repository.get()

            return data.certificate_name
        } catch (error) {
            if (error.response) {
                if (error.response.status && error.response.status === 422) {
                    const errorMessage =
                        error.response.data.data.message.cert[0]

                    return new ErrorHandler(errorMessage, 'http', 422)
                }
                const errorMessage = error.response.data.data.message

                return new ErrorHandler(errorMessage, 'http', 400)
            } else {
                const errorMessage = error.message

                return new ErrorHandler(errorMessage, 'http')
            }
        }
    }
}
