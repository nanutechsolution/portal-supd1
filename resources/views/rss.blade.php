{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $homeUrl }}</link>
        <atom:link href="{{ $selfUrl }}" rel="self" type="application/rss+xml" />
        <description>{{ $description }}</description>
        <language>id-ID</language>
        <lastBuildDate>{{ $lastBuildDate }}</lastBuildDate>
        <generator>Portal SUPD I</generator>

        @foreach ($items as $item)
            <item>
                <title>{{ $item['title'] }}</title>
                <link>{{ $item['link'] }}</link>
                <guid isPermaLink="true">{{ $item['guid'] }}</guid>
                <pubDate>{{ $item['pubDate'] }}</pubDate>
                <description>{{ $item['description'] }}</description>
                <dc:creator>{{ $item['author'] }}</dc:creator>
                @if (filled($item['category']))
                    <category>{{ $item['category'] }}</category>
                @endif
                @if ($item['enclosure'] !== null)
                    <enclosure url="{{ $item['enclosure']['url'] }}" type="{{ $item['enclosure']['type'] }}" length="{{ $item['enclosure']['length'] }}" />
                @endif
            </item>
        @endforeach
    </channel>
</rss>
