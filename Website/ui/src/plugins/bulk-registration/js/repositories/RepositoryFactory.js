import CsvUploadRepository from './CsvUploadRepository'

const repositories = {
    'csv': CsvUploadRepository,
}
export default {
    get: name => repositories[name]
}
