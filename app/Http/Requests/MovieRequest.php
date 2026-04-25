<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $movieId = $this->route('movie') ? $this->route('movie')->movie_id : null;
        $isCreating = $this->isMethod('POST');

        return [
            'title' => 'required|string|max:255|unique:movies,title,' . $movieId . ',movie_id',
            'duration' => 'required|integer|min:1',
            'release_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:release_date',
                $isCreating ? 'after_or_equal:today' : '',
            ],
            'description' => 'nullable|string',
            'language' => 'nullable|string|max:100',
            'age_limit' => 'nullable|integer',
            'status' => 'nullable|string|in:active,inactive',
            'rating' => 'nullable|numeric|between:0,10',
            'trailer_url' => 'nullable|url',
            'poster_url' => 'nullable|string',
            'backdrop_url' => 'nullable|string',
            'director' => 'nullable|string|max:255',
            'actors' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'producer' => 'nullable|string|max:255',
            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'exists:genres,genre_id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tên phim không được để trống.',
            'title.unique' => 'Tên phim này đã tồn tại.',
            'duration.required' => 'Thời lượng phim không được để trống.',
            'release_date.required' => 'Ngày khởi chiếu không được để trống.',
            'end_date.required' => 'Ngày kết thúc không được để trống.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày khởi chiếu và không được ở trong quá khứ.',
        ];
    }
}
