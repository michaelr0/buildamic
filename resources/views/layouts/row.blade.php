<div {!! BuildamicHelper()->HtmlId($row->buildamicSetting('attributes.id')) !!} {{ $row->computedAttribute('dataAtts') }} class="buildamic-row {{ $row->computedAttribute('class') }} {{ $row->computedAttribute('col_gap') }}" style="{{ $row->computedAttribute('column_count_total') }}">
    @foreach($row->value() as $column)
        {!! $buildamic->renderColumn($column) !!}
    @endforeach
</div>
